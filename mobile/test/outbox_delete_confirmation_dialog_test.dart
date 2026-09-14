import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gofran_mobile/features/home/presentation/home_screen.dart';

void main() {
  testWidgets(
    'rejected record warning explains that it was not accepted and cancel is safe',
    (tester) async {
      bool? result;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: Builder(
              builder: (context) => TextButton(
                onPressed: () async {
                  result = await showDialog<bool>(
                    context: context,
                    barrierDismissible: false,
                    builder: (_) => const OutboxDeleteConfirmationDialog(
                      status: 'rejected',
                    ),
                  );
                },
                child: const Text('فتح'),
              ),
            ),
          ),
        ),
      );

      await tester.tap(find.text('فتح'));
      await tester.pumpAndSettle();

      expect(find.text('حذف السجل المرفوض من الجهاز؟'), findsOneWidget);
      expect(find.textContaining('مرفوض ولم يُعتمد في النظام'), findsOneWidget);
      expect(
        find.textContaining('سيزيل النسخة المحلية نهائيًا'),
        findsOneWidget,
      );

      await tester.tap(find.text('إلغاء والاحتفاظ بالسجل'));
      await tester.pumpAndSettle();

      expect(result, isFalse);
      expect(find.byType(AlertDialog), findsNothing);
    },
  );

  testWidgets('conflict record requires an explicit permanent-delete action', (
    tester,
  ) async {
    bool? result;

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Builder(
            builder: (context) => TextButton(
              onPressed: () async {
                result = await showDialog<bool>(
                  context: context,
                  barrierDismissible: false,
                  builder: (_) =>
                      const OutboxDeleteConfirmationDialog(status: 'conflict'),
                );
              },
              child: const Text('فتح'),
            ),
          ),
        ),
      ),
    );

    await tester.tap(find.text('فتح'));
    await tester.pumpAndSettle();

    expect(find.text('حذف السجل المتعارض من الجهاز؟'), findsOneWidget);
    expect(find.textContaining('مراجعة السجل الرسمي'), findsOneWidget);

    await tester.tap(find.text('حذف نهائي من الجهاز'));
    await tester.pumpAndSettle();

    expect(result, isTrue);
    expect(find.byType(AlertDialog), findsNothing);
  });
}
